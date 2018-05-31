//*********************************************************************************************
// Author: Jeff Emshey
// File: UserManagementConsole.cs
// Description: This form acts as the hub to the connection info, contact sorting lists
//              and contact distribution manager.
// Last Updated: May 16, 2018
// Updates Made By: Jeff Emshey
//*********************************************************************************************

#region Using Statements
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.DirectoryServices;
using System.Collections;
using Microsoft.Exchange.WebServices.Data;
using System.IO;
using System.Collections.ObjectModel;
using System.Management.Automation;
using Microsoft.VisualBasic.FileIO;
#endregion

#region WindowsFormApp
namespace WindowsFormsApp3
{
    public partial class Form1 : Form
    {
        //Global String lists that hold names and emails of mobile users for UCM population
        List<String> nameHolder = new List<String> { };
        List<String> emailHolder = new List<String> { };

        public Form1()
        {
            InitializeComponent();
        }

        #region Tab Population Function
        //This function populates the counts and mobile users fields depending on the selected tab
        private void UserStaticsTab_Click(object sender, EventArgs e)
        {
            //Populate the user statistics area on tab 3
            if (tabControl.SelectedTab == tabControl.TabPages["tabPage3"])
            {
                UserStatisticsList.Items.Clear();
                populateUserStatisticsList();
            }
            //set the counter and date changed values for the mobile user and external contacts lists on tab 2
            else if (tabControl.SelectedTab == tabControl.TabPages["tabPage2"])
            {
                //Re-usable
                int entryCounter = 0;

                //If both the GAL file and Inclusion file exist
                if (File.Exists(GalFileNameAndPath) && File.Exists(inclusionFileNameAndPath))
                {
                    //Set the last updated field
                    MobileUsersUpdate.Text = System.IO.File.GetLastWriteTime(GalFileNameAndPath).ToString();

                    //Count GAL entries
                    entryCounter += getCounts(GalFileNameAndPath);

                    //Count Mobile Users entries
                    entryCounter += getCounts(inclusionFileNameAndPath);
                    MobileUsersCount.Text = entryCounter.ToString(); //Display the count
                }
                //If there is no files to pull data from, set to err values
                else
                {
                    MobileUsersCount.Text = "-";
                    MobileUsersUpdate.Text = "No File Exists";
                }

                entryCounter = 0; //reset counter for External counts

                //If the External file exists
                if (File.Exists(ExternalFileNameAndPath))
                {
                    //Set the last updated field
                    ExternalContactUpdate.Text = System.IO.File.GetLastWriteTime(ExternalFileNameAndPath).ToString();

                    //Count the External File
                    entryCounter = getCounts(ExternalFileNameAndPath);
                    ExternalContactsCount.Text = entryCounter.ToString(); //Display the count
                }
                //If there is no files to pull data from, set to err values
                else
                {
                    ExternalContactsCount.Text = "-";
                    ExternalContactUpdate.Text = "No File Exists";
                }  
            }
            return;
        }
        #endregion
        #region Count Contacts Function
        int getCounts(String fileName)
        {
            int entryCounter = 0;
            String line;

            StreamReader reader = new StreamReader(fileName);
            while ((line = reader.ReadLine()) != null)
            {
                if (!line.Contains("Title"))
                {
                    entryCounter++;
                }
            }
            reader.Close();
            return entryCounter;
        }
        #endregion
        #region Populate User Statistics List Function
        //Function to populate the User Statistic list
        private void populateUserStatisticsList()
        {
            //Inclusion File Credentials
            String line;
            string[] row = new string[35]; //Used to parse data from csv document

            //Attempt to open the inclusion file from previous sessions. This will populate the UserStatistics list where contacts can be pushed to
            if (File.Exists(inclusionFileNameAndPath))
            {
                StreamReader reader = new StreamReader(inclusionFileNameAndPath);
                while ((line = reader.ReadLine()) != null)
                {
                    if (!line.Contains("Title"))
                    {
                        row = line.Split(','); //Comma delimiter

                        nameHolder.Add(row[27]); //Data from current rol, col 27 (Email Display Name)
                        emailHolder.Add(row[26]);//Data from current rol, col 26 (Email)
                        //Format the selection item for display purposes.
                        UserStatisticsList.Items.Add(String.Format("{0,-18} | {1,-35} | {2,-30}", row[27], row[26], row[25]));
                    }
                }
                reader.Close();
            }
            return;
        }
        #endregion
        #region Open Contacts Editor
        private void editLists_Click(object sender, EventArgs e)
        {
            //Determine the sender
            Button name = (Button)sender;

            //Check to ensure only one editor is open at a time
            Form fc = Application.OpenForms["MobileUsersForm"];
            Form fc2 = Application.OpenForms["ExternalContactsForm"];

            if (name.Name.Equals("mobileUsersEditBtn"))
            {
                //Check to ensure only one editor is open at a time
                if (fc == null && fc2 == null)
                {
                    //Create the Mobile Users form and send the LDAP connection info
                    MobileUsersForm mobileUsersForm = new MobileUsersForm(OU.Text, Server.Text);
                    mobileUsersForm.Show(this);
                }
            }
            else
            {
                //Check to ensure only one editor is open at a time
                if (fc == null && fc2 == null)
                {
                    //Create the External Contacts form and send the LDAP connection info
                    ExternalContactsForm externalContactsForm = new ExternalContactsForm();
                    externalContactsForm.Show(this);
                }
            }
            return;
        }
        #endregion
        #region UCM Add Contacts Function
        private void AddContact_Click(object sender, EventArgs e)
        {
            RunUCMBtn.Enabled = false;
            String contactDisplayInfo = UserStatisticsList.SelectedItem.ToString(); //Takes all information from the selected item
            String contactEmailDisplayName = "";
            String contactDisplayName = "";

            //Use the selected line of text to find the name and email in the name and email String lists
            for (int i = 0; i < nameHolder.Count; i++)
            {
                if (contactDisplayInfo.Contains(nameHolder[i]))
                {
                    contactDisplayName = nameHolder[i];
                }
            }
            for (int i = 0; i < emailHolder.Count; i++)
            {
                if (contactDisplayInfo.Contains(emailHolder[i]))
                {
                    contactEmailDisplayName = emailHolder[i];
                }
            }
 
            //Temp file
            String inclusionTempFile = System.IO.Path.Combine(path, "InclusionContactsListTemp.csv");

            //Connect to the exchange server
            ExchangeService service = new ExchangeService(ExchangeVersion.Exchange2013); //Exchange version
            /* HIDDEN - NOT FOR PORTFOLIO VIEW */ //Admin login + domain
            /* HIDDEN - NOT FOR PORTFOLIO VIEW */ //URL to exchange XML file (required)

            //Exchange functions
            service.ImpersonatedUserId = new ImpersonatedUserId(ConnectingIdType.SmtpAddress, contactEmailDisplayName); //Impersionation request
            FolderId contactsFolder = new FolderId(WellKnownFolderName.Contacts, contactEmailDisplayName); //Folder of impersonated user

            //Create a view to load extra fields from the user's contacts folder
            /* HIDDEN - NOT FOR PORTFOLIO VIEW */

                //Find the Body (comment) and the Display Name
				/* HIDDEN - NOT FOR PORTFOLIO VIEW */

                //find the Contact items in the view
				/* HIDDEN - NOT FOR PORTFOLIO VIEW */

                //Remove all contacts created by this software or legacy Itrezzo software before continuing
                service.LoadPropertiesForItems(contactItems.Items, PropSet); //Load the specified properties from earlier
                foreach (Item item in contactItems)
                {
                    if (item is Microsoft.Exchange.WebServices.Data.Contact) //Make sure the Item is a Contact by propety
                    {
                        //Convert it to an actual Contact
                        Microsoft.Exchange.WebServices.Data.Contact contact3 = item as Microsoft.Exchange.WebServices.Data.Contact;
                        //Check if the contact was either created by this software or the legacy itrezzo software via the comment
                        if (item.Body.Text != null && (item.Body.Text.Contains("This contact was created by your I.T. department")
                            || item.Body.Text.Contains("This contact was created by the itrezzoAgent Emergency Preparedness Software and " +
                                                       "will be recreated if deleted. It is maintained automatically from a central database.")))
                        {
                            contact3.Delete(DeleteMode.HardDelete, true); //If it was, delete the contact
                        }
                    }
                }
            }

            //Add the Inclusion List (Mobile Users) entries as Contacts
            if (File.Exists(inclusionFileNameAndPath))
            {
                CreateContacts(inclusionFileNameAndPath, service, contactsFolder, contactDisplayName);
            }

            //Add the GAL List (users who are contacts but are not company smart phone users) entries as Contacts
            if (File.Exists(GalFileNameAndPath))
            {
                CreateContacts(GalFileNameAndPath, service, contactsFolder, contactDisplayName);
            }

            //Add the External List (custom created contacts) entries as Contacts
            if (File.Exists(ExternalFileNameAndPath))
            {
                CreateContacts(ExternalFileNameAndPath, service, contactsFolder, contactDisplayName);
            }

            //Once all the contacts are created, update the "up-to-date" field for the user it was run on
            string[] parser = new string[35];
            String searcher;
            String newLineString = "";

            if (File.Exists(inclusionFileNameAndPath))
            {
                StreamWriter writer = File.AppendText(inclusionTempFile);
                StreamReader reader = new StreamReader(inclusionFileNameAndPath);
                while ((searcher = reader.ReadLine()) != null)
                {
                    parser = searcher.Split(',');
                    if (searcher.Contains(contactDisplayName)) //Find the row with the selected contact
                    {
                        parser[25] = "Yes"; //Change the value
                        for(int i = 0; i < parser.Length; i++)
                        {
                            newLineString += parser[i] + ",";
                        }
                        writer.WriteLine(newLineString);
                    }
                    else
                    {
                        writer.WriteLine(searcher);
                    }
                }
                writer.Close();
                reader.Close();
            }
            //Swap the old file with the temp file
            File.Delete(inclusionFileNameAndPath);
            File.Move(inclusionTempFile, inclusionFileNameAndPath);

            //Clear and repopulate the User Statistics list to show the updated data
            UserStatisticsList.Items.Clear();
            populateUserStatisticsList();
            ErrBox.Text += "Complete...." + Environment.NewLine; 
            return;
        }
        #endregion
        #region Create Contacts Function
        void CreateContacts(String fileName, ExchangeService service, FolderId contactsFolder, String contactDisplayName)
        {
            String line;
            string[] row = new string[35];
            StreamReader reader = new StreamReader(fileName);
            while ((line = reader.ReadLine()) != null)
            {
                var contact = new Microsoft.Exchange.WebServices.Data.Contact(service); //Create a Var that has Contact properties

                //Load extended properties for the contact.This is required to set the respective fields for the contact                                                                 
				/* HIDDEN - NOT FOR PORTFOLIO VIEW */

                //Parse the row of the file
                if (!line.Contains("Title"))
                {
                    row = line.Split(',');
					//Specify the display name and general info
                    /* HIDDEN - NOT FOR PORTFOLIO VIEW */

                    // Specify the home address
                    /* HIDDEN - NOT FOR PORTFOLIO VIEW */

                    // Specify the business address
                    /* HIDDEN - NOT FOR PORTFOLIO VIEW */

                    // Specify the business, home, and car phone numbers
                    /* HIDDEN - NOT FOR PORTFOLIO VIEW */

                    // Specifyemail addresses
                    /* HIDDEN - NOT FOR PORTFOLIO VIEW */
					
                    if (contact.DisplayName != contactDisplayName) //Check to stop duplication
                    {
                        contact.Save(contactsFolder); //Create and save the contact
                    }
                }
            }
            reader.Close();

            return;
        }
        #endregion
        #region UCM Button Functionality Function
        private void UCMContactsList_SelectedValueChanged(object sender, EventArgs e)
        {
            if (sender != null)
            {
                RunUCMBtn.Enabled = true;
            }
            else
            {
                RunUCMBtn.Enabled = false;
            } 
            return;
        }
        #endregion
    }
    #endregion
}
#endregion